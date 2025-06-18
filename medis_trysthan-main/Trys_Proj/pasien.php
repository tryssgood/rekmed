<?php
session_start();
include 'config/database.php';

// Get list of polyclinics for the add/edit modals
$poliklinik_options = [];
$result_poli = $conn->query("SELECT id_poli, nm_poli FROM poliklinik ORDER BY nm_poli");
if ($result_poli->num_rows > 0) {
    while ($row_poli = $result_poli->fetch_assoc()) {
        $poliklinik_options[] = $row_poli;
    }
}

include 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$page_title = "Data Pasien";
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
            
            // --- START OF NEW CODE FOR KUNJUNGAN DATA ---
            $id_poli = $_POST['id_poli'];
            $tgl_kunjungan = $_POST['tgl_kunjungan'];
            $jam_kunjungan = $_POST['jam_kunjungan'];
            // --- END OF NEW CODE FOR KUNJUNGAN DATA ---
            
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
                
                $id_pasien_baru = $conn->insert_id; // Get the newly created id_pasien

                // --- START OF NEW CODE FOR INSERTING KUNJUNGAN ---
                // INSERT NEW VISIT RECORD INTO 'kunjungan' TABLE
                $status_kunjungan_default = 'Menunggu'; // Sesuaikan dengan default status di tabel kunjungan Anda
                $keluhan_default = ''; // Bisa diisi string kosong atau NULL jika keluhan tidak wajib saat pendaftaran awal

                $stmt = $conn->prepare("INSERT INTO kunjungan (id_pasien, id_poli, tgl_kunjungan, jam_kunjungan, status, keluhan) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissss", $id_pasien_baru, $id_poli, $tgl_kunjungan, $jam_kunjungan, $status_kunjungan_default, $keluhan_default);
                $stmt->execute();
                // --- END OF NEW CODE FOR INSERTING KUNJUNGAN ---

                // Commit transaction
                $conn->commit(); // Pastikan commit() berada di sini setelah kedua INSERT berhasil
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
                // --- START OF NEW CODE FOR DELETING KUNJUNGAN ---
                // Delete associated visit records first to maintain referential integrity
                $stmt = $conn->prepare("DELETE FROM kunjungan WHERE id_pasien = ?");
                $stmt->bind_param("i", $id_pasien);
                $stmt->execute();
                // --- END OF NEW CODE FOR DELETING KUNJUNGAN ---

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
        }
    }
}

// Get patient data
$sql = "SELECT * FROM pasien ORDER BY nm_pasien";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Rekam Medis - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="content">
            <div class="content-header">
                <h2><?php echo $page_title; ?></h2>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Tambah Pasien
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
                                        <button class="btn btn-sm btn-info" onclick="showEditModal(<?php echo $row['id_pasien']; ?>, '<?php echo $row['nm_pasien']; ?>', '<?php echo $row['tgl_lhr']; ?>', '<?php echo $row['alamat']; ?>', '<?php echo $row['telepon']; ?>', '<?php echo $row['no_hp']; ?>', '<?php echo $row['jenis_kelamin']; ?>', '<?php echo $row['nm_kk']; ?>', '<?php echo $row['hub_kel']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id_pasien']; ?>, '<?php echo $row['nm_pasien']; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <a href="rekam_medis_view.php?id=<?php echo $row['id_pasien']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-notes-medical"></i>
                                        </a>
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
                </div>
            </div>
        </div>
    </div>
    
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Pasien Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="nm_pasien">Nama Pasien</label>
                        <input type="text" id="nm_pasien" name="nm_pasien" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tgl_lhr">Tanggal Lahir</label>
                        <input type="date" id="tgl_lhr" name="tgl_lhr" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="telepon">Telepon</label>
                        <input type="text" id="telepon" name="telepon">
                    </div>
                    
                    <div class="form-group">
                        <label for="no_hp">No. HP</label>
                        <input type="text" id="no_hp" name="no_hp" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nm_kk">Nama Kepala Keluarga</label>
                        <input type="text" id="nm_kk" name="nm_kk">
                    </div>
                    
                    <div class="form-group">
                        <label for="hub_kel">Hubungan Keluarga</label>
                        <input type="text" id="hub_kel" name="hub_kel">
                    </div>
                    
                    <div class="form-group">
                        <label for="id_poli">Poliklinik Tujuan</label>
                        <select id="id_poli" name="id_poli" required>
                            <option value="">Pilih Poliklinik</option>
                            <?php foreach ($poliklinik_options as $poli): ?>
                                <option value="<?php echo $poli['id_poli']; ?>"><?php echo $poli['nm_poli']; ?></option>
                            <?php endforeach; ?> </select>
                    </div>

                    <div class="form-group">
                        <label for="tgl_kunjungan">Tanggal Kunjungan</label>
                        <input type="date" id="tgl_kunjungan" name="tgl_kunjungan" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="jam_kunjungan">Jam Kunjungan</label>
                        <input type="time" id="jam_kunjungan" name="jam_kunjungan" value="<?php echo date('H:i'); ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Data Pasien</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id_pasien" name="id_pasien">
                    
                    <div class="form-group">
                        <label for="edit_nm_pasien">Nama Pasien</label>
                        <input type="text" id="edit_nm_pasien" name="nm_pasien" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_tgl_lhr">Tanggal Lahir</label>
                        <input type="date" id="edit_tgl_lhr" name="tgl_lhr" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_jenis_kelamin">Jenis Kelamin</label>
                        <select id="edit_jenis_kelamin" name="jenis_kelamin" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_alamat">Alamat</label>
                        <textarea id="edit_alamat" name="alamat" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_telepon">Telepon</label>
                        <input type="text" id="edit_telepon" name="telepon">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_no_hp">No. HP</label>
                        <input type="text" id="edit_no_hp" name="no_hp" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_nm_kk">Nama Kepala Keluarga</label>
                        <input type="text" id="edit_nm_kk" name="nm_kk">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_hub_kel">Hubungan Keluarga</label>
                        <input type="text" id="edit_hub_kel" name="hub_kel">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Konfirmasi Hapus</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus pasien <span id="delete_name"></span>?</p>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete_id_pasien" name="id_pasien">
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger">Hapus</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Show add modal
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
            // Set default date and time
            document.getElementById('tgl_kunjungan').valueAsDate = new Date();
            document.getElementById('jam_kunjungan').value = new Date().toTimeString().slice(0,5);
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
    </script>
</body>
</html>