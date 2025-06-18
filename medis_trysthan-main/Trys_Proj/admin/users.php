<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Manajemen Pengguna";
$message = '';

// Process form submission for adding/editing user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];
            
            $stmt = $conn->prepare("INSERT INTO login (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $password, $role);
            
            if ($stmt->execute()) {
                $message = "Pengguna berhasil ditambahkan.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'edit') {
            $id_user = $_POST['id_user'];
            $username = $_POST['username'];
            $role = $_POST['role'];
            
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE login SET username = ?, password = ?, role = ? WHERE id_user = ?");
                $stmt->bind_param("sssi", $username, $password, $role, $id_user);
            } else {
                $stmt = $conn->prepare("UPDATE login SET username = ?, role = ? WHERE id_user = ?");
                $stmt->bind_param("ssi", $username, $role, $id_user);
            }
            
            if ($stmt->execute()) {
                $message = "Data pengguna berhasil diperbarui.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'delete') {
            $id_user = $_POST['id_user'];
            
            // Don't allow deleting current user
            if ($id_user == $_SESSION['user_id']) {
                $message = "Tidak dapat menghapus akun yang sedang digunakan.";
            } else {
                $stmt = $conn->prepare("DELETE FROM login WHERE id_user = ?");
                $stmt->bind_param("i", $id_user);
                
                if ($stmt->execute()) {
                    $message = "Pengguna berhasil dihapus.";
                } else {
                    $message = "Error: " . $stmt->error;
                }
            }
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role_filter']) ? $_GET['role_filter'] : '';

$search_condition = '';
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(l.username LIKE '%$search%')";
}

if (!empty($role_filter)) {
    $conditions[] = "l.role = '$role_filter'";
}

if (!empty($conditions)) {
    $search_condition = "WHERE " . implode(" AND ", $conditions);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$total_records_query = "SELECT COUNT(*) as count FROM login l $search_condition";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

// Get user data with additional info
$sql = "SELECT l.*, 
               CASE 
                   WHEN l.role = 'pasien' THEN p.nm_pasien
                   WHEN l.role = 'dokter' THEN d.nm_dokter
                   WHEN l.role = 'admin' THEN ap.nm_admin
                   ELSE 'N/A'
               END as full_name,
               CASE 
                   WHEN l.role = 'pasien' THEN p.no_rm
                   WHEN l.role = 'dokter' THEN d.SIP
                   ELSE NULL
               END as identifier
        FROM login l
        LEFT JOIN pasien p ON l.id_user = p.id_user AND l.role = 'pasien'
        LEFT JOIN dokter d ON l.id_user = d.id_user AND l.role = 'dokter'
        LEFT JOIN admin_profile ap ON l.id_user = ap.id_user AND l.role = 'admin'
        $search_condition 
        ORDER BY l.created_at DESC 
        LIMIT $offset, $limit";
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
                <h2><i class="fas fa-users"></i> <?php echo $page_title; ?></h2>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Tambah Pengguna
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
                        <input type="text" name="search" placeholder="Cari username..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="role_filter" class="role-filter">
                            <option value="">Semua Role</option>
                            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="dokter" <?php echo $role_filter == 'dokter' ? 'selected' : ''; ?>>Dokter</option>
                            <option value="pasien" <?php echo $role_filter == 'pasien' ? 'selected' : ''; ?>>Pasien</option>
                        </select>
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <div class="filter-options">
                    <button class="btn btn-sm btn-outline" onclick="exportData('csv')">
                        <i class="fas fa-file-csv"></i> Export CSV
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
                                <th>Username</th>
                                <th>Role</th>
                                <th>Nama Lengkap</th>
                                <th>Identifier</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['username']; ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $row['role']; ?>">
                                            <?php echo ucfirst($row['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['full_name'] ?? 'N/A'; ?></td>
                                    <td><?php echo $row['identifier'] ?? '-'; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info" onclick="showEditModal(<?php echo $row['id_user']; ?>, '<?php echo addslashes($row['username']); ?>', '<?php echo $row['role']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($row['id_user'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id_user']; ?>, '<?php echo addslashes($row['username']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-warning" onclick="confirmResetPassword(<?php echo $row['id_user']; ?>, '<?php echo addslashes($row['username']); ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data pengguna.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo urlencode($role_filter); ?>" class="pagination-link">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo urlencode($role_filter); ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo urlencode($role_filter); ?>" class="pagination-link">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Tambah Pengguna Baru</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="addUserForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="password" name="password" required>
                        <small>Minimal 6 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="role"><i class="fas fa-user-tag"></i> Role</label>
                        <select id="role" name="role" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="admin">Admin</option>
                            <option value="dokter">Dokter</option>
                            <option value="pasien">Pasien</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Catatan:</strong> Setelah membuat akun, Anda perlu menambahkan data profil sesuai role di menu yang sesuai.
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
    
    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Edit Pengguna</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="editUserForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id_user" name="id_user">
                    
                    <div class="form-group">
                        <label for="edit_username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_password"><i class="fas fa-lock"></i> Password Baru</label>
                        <input type="password" id="edit_password" name="password">
                        <small>Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_role"><i class="fas fa-user-tag"></i> Role</label>
                        <select id="edit_role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="dokter">Dokter</option>
                            <option value="pasien">Pasien</option>
                        </select>
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
                <p>Apakah Anda yakin ingin menghapus pengguna <span id="delete_name" class="font-weight-bold"></span>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-circle"></i> Perhatian: Tindakan ini akan menghapus semua data terkait pengguna dan tidak dapat dibatalkan!</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete_id_user" name="id_user">
                    
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
        // Show add modal
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        // Show edit modal
        function showEditModal(id, username, role) {
            document.getElementById('edit_id_user').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_password').value = '';
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Show delete confirmation modal
        function confirmDelete(id, username) {
            document.getElementById('delete_id_user').value = id;
            document.getElementById('delete_name').textContent = username;
            
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Show reset password confirmation modal
        function confirmResetPassword(id, username) {
            if (confirm(`Apakah Anda yakin ingin mereset password untuk pengguna ${username}?`)) {
                // Create a form to submit reset password request
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'reset_password';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id_user';
                idInput.value = id;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
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
            const roleParam = '<?php echo urlencode($role_filter); ?>';
            window.location.href = `export_users.php?format=${format}&search=${searchParam}&role_filter=${roleParam}`;
        }
        
        // Print data
        function printData() {
            window.print();
        }
        
        // Form validation
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            validateUserForm(e, this);
        });
        
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            validateUserForm(e, this);
        });
        
        function validateUserForm(e, form) {
            const password = form.querySelector('[name="password"]').value;
            
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter.');
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
        
        .role-filter {
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
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-admin {
            background-color: #dc3545;
            color: white;
        }
        
        .role-dokter {
            background-color: #28a745;
            color: white;
        }
        
        .role-pasien {
            background-color: #17a2b8;
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
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left-color: #17a2b8;
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
            
            .search-input {
                flex-direction: column;
            }
            
            .role-filter {
                border-left: none;
                border-top: 1px solid #e9ecef;
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
