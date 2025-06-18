<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dokter') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Daftar Pasien";
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nm_pasien = $_POST['nm_pasien'];
                $alamat = $_POST['alamat'];
                $no_ktp = $_POST['no_ktp'];
                $no_hp = $_POST['no_hp'];
                $jenis_kelamin = $_POST['jenis_kelamin'];
                $tgl_lhr = $_POST['tgl_lhr'];
                
                // Generate medical record number
                $no_rm = generateMedicalRecordNumber($conn);
                
                // Create user account first
                $username = 'pasien_' . time();
                $password = password_hash('password123', PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO login (username, password, role) VALUES (?, ?, 'pasien')");
                $stmt->bind_param("ss", $username, $password);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Insert patient
                    $stmt = $conn->prepare("INSERT INTO pasien (nm_pasien, alamat, no_ktp, no_hp, jenis_kelamin, tgl_lhr, no_rm, id_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssi", $nm_pasien, $alamat, $no_ktp, $no_hp, $jenis_kelamin, $tgl_lhr, $no_rm, $user_id);
                    
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Pasien berhasil ditambahkan dengan No. RM: ' . $no_rm . '</div>';
                    } else {
                        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menambahkan pasien: ' . $conn->error . '</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal membuat akun user: ' . $conn->error . '</div>';
                }
                break;
                
            case 'edit':
                $id_pasien = $_POST['id_pasien'];
                $nm_pasien = $_POST['nm_pasien'];
                $alamat = $_POST['alamat'];
                $no_ktp = $_POST['no_ktp'];
                $no_hp = $_POST['no_hp'];
                $jenis_kelamin = $_POST['jenis_kelamin'];
                $tgl_lhr = $_POST['tgl_lhr'];
                
                $stmt = $conn->prepare("UPDATE pasien SET nm_pasien=?, alamat=?, no_ktp=?, no_hp=?, jenis_kelamin=?, tgl_lhr=? WHERE id_pasien=?");
                $stmt->bind_param("ssssssi", $nm_pasien, $alamat, $no_ktp, $no_hp, $jenis_kelamin, $tgl_lhr, $id_pasien);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Data pasien berhasil diperbarui!</div>';
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal memperbarui data pasien: ' . $conn->error . '</div>';
                }
                break;
                
            case 'delete':
                $id_pasien = $_POST['id_pasien'];
                
                // Get user_id first
                $user_result = $conn->query("SELECT id_user FROM pasien WHERE id_pasien = $id_pasien");
                if ($user_result->num_rows > 0) {
                    $user_data = $user_result->fetch_assoc();
                    $user_id = $user_data['id_user'];
                    
                    // Delete patient
                    if ($conn->query("DELETE FROM pasien WHERE id_pasien = $id_pasien")) {
                        // Delete user account
                        $conn->query("DELETE FROM login WHERE id_user = $user_id");
                        $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Pasien berhasil dihapus!</div>';
                    } else {
                        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menghapus pasien: ' . $conn->error . '</div>';
                    }
                }
                break;
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = "";

if (!empty($search)) {
    $search_condition = "WHERE (p.nm_pasien LIKE '%$search%' OR p.no_rm LIKE '%$search%' OR p.no_ktp LIKE '%$search%')";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_records_query = "SELECT COUNT(*) as count FROM pasien p $search_condition";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

// Get patients
$sql = "SELECT p.*, l.username 
        FROM pasien p 
        LEFT JOIN login l ON p.id_user = l.id_user 
        $search_condition 
        ORDER BY p.nm_pasien 
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
    
    <div class="main-container">
        <div class="content-header">
            <h2><i class="fas fa-users"></i> <?php echo $page_title; ?></h2>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Tambah Pasien
            </button>
        </div>
        
        <?php echo $message; ?>
        
        <!-- Search and Filter -->
        <div class="table-filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari pasien..." value="<?php echo htmlspecialchars($search); ?>">
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
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>Jenis Kelamin</th>
                        <th>Tanggal Lahir</th>
                        <th>No Telepon</th>
                        <th>Alamat</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $row['no_rm']; ?></strong></td>
                            <td><?php echo $row['nm_pasien']; ?></td>
                            
                            <td>
                                <span class="badge <?php echo $row['jenis_kelamin'] == 'L' ? 'badge-info' : 'badge-danger'; ?>">
                                    <?php echo $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($row['tgl_lhr'])); ?></td>
                            <td><?php echo $row['no_hp']; ?></td>
                            <td><?php echo substr($row['alamat'], 0, 30) . (strlen($row['alamat']) > 30 ? '...' : ''); ?></td>
                            
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data pasien</td>
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
    
    <!-- Add Patient Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Tambah Pasien Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nama Lengkap</label>
                        <input type="text" name="nm_pasien" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> No. KTP</label>
                        <input type="text" name="no_ktp" required maxlength="16">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> No. HP</label>
                        <input type="text" name="no_hp" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-venus-mars"></i> Jenis Kelamin</label>
                        <select name="jenis_kelamin" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Tanggal Lahir</label>
                        <input type="date" name="tgl_lhr" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                        <textarea name="alamat" rows="3" required></textarea>
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
    
    <!-- Edit Patient Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Edit Data Pasien</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_pasien" id="edit_id_pasien">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nama Lengkap</label>
                        <input type="text" name="nm_pasien" id="edit_nm_pasien" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> No. KTP</label>
                        <input type="text" name="no_ktp" id="edit_no_ktp" required maxlength="16">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> No. HP</label>
                        <input type="text" name="no_hp" id="edit_no_hp" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-venus-mars"></i> Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="edit_jenis_kelamin" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Tanggal Lahir</label>
                        <input type="date" name="tgl_lhr" id="edit_tgl_lhr" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                        <textarea name="alamat" id="edit_alamat" rows="3" required></textarea>
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
    
    <!-- View Patient Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user"></i> Detail Pasien</h3>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="patientDetails"></div>
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
                <p>Apakah Anda yakin ingin menghapus data pasien ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_pasien" id="delete_id_pasien">
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
        
        // View patient details
        function viewPatient(id) {
            fetch(`get_patient.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const patient = data.patient;
                        const age = calculateAge(patient.tgl_lhr);
                        document.getElementById('patientDetails').innerHTML = `
                            <div class="patient-info-grid">
                                <div class="info-card">
                                    <h4><i class="fas fa-id-badge"></i> Informasi Dasar</h4>
                                    <p><strong>No. RM:</strong> ${patient.no_rm}</p>
                                    <p><strong>Nama:</strong> ${patient.nm_pasien}</p>
                                    <p><strong>No. KTP:</strong> ${patient.no_ktp}</p>
                                    <p><strong>Jenis Kelamin:</strong> ${patient.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</p>
                                </div>
                                <div class="info-card">
                                    <h4><i class="fas fa-calendar"></i> Data Kelahiran</h4>
                                    <p><strong>Tanggal Lahir:</strong> ${formatDate(patient.tgl_lhr)}</p>
                                    <p><strong>Usia:</strong> ${age} tahun</p>
                                </div>
                                <div class="info-card">
                                    <h4><i class="fas fa-phone"></i> Kontak</h4>
                                    <p><strong>No. HP:</strong> ${patient.no_hp}</p>
                                    <p><strong>Alamat:</strong> ${patient.alamat}</p>
                                </div>
                            </div>
                        `;
                        openModal('viewModal');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Edit patient
        function editPatient(id) {
            fetch(`get_patient.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const patient = data.patient;
                        document.getElementById('edit_id_pasien').value = patient.id_pasien;
                        document.getElementById('edit_nm_pasien').value = patient.nm_pasien;
                        document.getElementById('edit_no_ktp').value = patient.no_ktp;
                        document.getElementById('edit_no_hp').value = patient.no_hp;
                        document.getElementById('edit_jenis_kelamin').value = patient.jenis_kelamin;
                        document.getElementById('edit_tgl_lhr').value = patient.tgl_lhr;
                        document.getElementById('edit_alamat').value = patient.alamat;
                        openModal('editModal');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Delete patient
        function deletePatient(id) {
            document.getElementById('delete_id_pasien').value = id;
            openModal('deleteModal');
        }
        
        // Utility functions
        function calculateAge(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        function exportData() {
            window.open('export_patients.php', '_blank');
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
