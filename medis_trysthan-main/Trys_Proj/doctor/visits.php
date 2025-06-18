<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dokter') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Daftar Kunjungan Pasien"; 
$message = '';
$doctor_id = getDoctorId($conn, $_SESSION['user_id']);

// Get doctor's polyclinic
$doctor_info = null;
if (!empty($doctor_id) && is_numeric($doctor_id)) {
    $stmt = $conn->prepare("SELECT id_poli FROM dokter WHERE id_dokter = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result_doctor_info = $stmt->get_result(); 
    if ($result_doctor_info->num_rows > 0) {
        $doctor_info = $result_doctor_info->fetch_assoc();
    }
    $stmt->close();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $id_pasien = $_POST['id_pasien'];
                $tgl_kunjungan = $_POST['tgl_kunjungan'];
                $jam_kunjungan = $_POST['jam_kunjungan'];
                $diagnosa_add = $_POST['diagnosa'] ?? ''; 
                $tindakan_add = $_POST['tindakan'] ?? ''; // Ganti dari keterangan_add
                $id_poli = $doctor_info['id_poli'] ?? null; 

                if ($id_poli) {
                    // Start transaction
                    $conn->begin_transaction();
                    try {
                        // Insert into kunjungan table
                        $stmt = $conn->prepare("INSERT INTO kunjungan (id_pasien, id_poli, tgl_kunjungan, jam_kunjungan, status_kunjungan, id_dokter) VALUES (?, ?, ?, ?, 'menunggu', ?)");
                        $stmt->bind_param("iissi", $id_pasien, $id_poli, $tgl_kunjungan, $jam_kunjungan, $doctor_id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Gagal menambahkan jadwal kunjungan: " . $stmt->error);
                        }
                        $last_kunjungan_id = $conn->insert_id; 
                        $stmt->close();

                        // Insert into rekam_medis table
                        // Use CURDATE() for tgl_rekam_medis if it's the current date, or a specific date if available
                        $stmt_rm_add = $conn->prepare("INSERT INTO rekam_medis (id_kunjungan, id_dokter, id_pasien, tgl_rekam_medis, diagnosa, tindakan) VALUES (?, ?, ?, CURDATE(), ?, ?)"); // Ganti keterangan
                        
                        if (empty($doctor_id) || empty($id_pasien)) {
                            throw new Exception("ID Dokter atau ID Pasien tidak valid untuk Rekam Medis.");
                        }
                        $stmt_rm_add->bind_param("iiiss", $last_kunjungan_id, $doctor_id, $id_pasien, $diagnosa_add, $tindakan_add); // Ganti keterangan_add
                        
                        if (!$stmt_rm_add->execute()) {
                            throw new Exception("Gagal menambahkan rekam medis: " . $stmt_rm_add->error);
                        }
                        $stmt_rm_add->close();

                        $conn->commit(); 
                        $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal kunjungan dan Rekam Medis berhasil ditambahkan!</div>';

                    } catch (Exception $e) {
                        $conn->rollback(); 
                        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . $e->getMessage() . '</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menambahkan jadwal: Informasi poliklinik dokter tidak ditemukan.</div>';
                }
                break;
                
            case 'edit':
                $id_kunjungan = $_POST['id_kunjungan'];
                $id_pasien = $_POST['id_pasien'];
                $tgl_kunjungan = $_POST['tgl_kunjungan'];
                $jam_kunjungan = $_POST['jam_kunjungan'];
                $diagnosa = $_POST['diagnosa'] ?? ''; 
                $tindakan = $_POST['tindakan'] ?? ''; // Ganti dari keterangan

                // Start transaction for edit
                $conn->begin_transaction();
                try {
                    // Update kunjungan table
                    $stmt = $conn->prepare("UPDATE kunjungan SET id_pasien=?, tgl_kunjungan=?, jam_kunjungan=? WHERE id_kunjungan=?");
                    $stmt->bind_param("issi", $id_pasien, $tgl_kunjungan, $jam_kunjungan, $id_kunjungan);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Gagal memperbarui jadwal kunjungan: " . $stmt->error);
                    }
                    $stmt->close();

                    // Check if rekam medis for this visit already exists
                    $check_rm_sql = "SELECT id_rekam_medis FROM rekam_medis WHERE id_kunjungan = ?";
                    $stmt_check_rm = $conn->prepare($check_rm_sql);
                    $stmt_check_rm->bind_param("i", $id_kunjungan);
                    $stmt_check_rm->execute();
                    $result_check_rm = $stmt_check_rm->get_result();
                    $rekam_medis_exists = $result_check_rm->num_rows > 0;
                    $stmt_check_rm->close();

                    if ($rekam_medis_exists) {
                        // If it exists, update rekam medis
                        $update_rm_sql = "UPDATE rekam_medis SET diagnosa = ?, tindakan = ? WHERE id_kunjungan = ?"; // Ganti keterangan
                        $stmt_update_rm = $conn->prepare($update_rm_sql);
                        $stmt_update_rm->bind_param("ssi", $diagnosa, $tindakan, $id_kunjungan); // Ganti keterangan
                        if (!$stmt_update_rm->execute()) {
                            throw new Exception("Gagal memperbarui rekam medis: " . $stmt_update_rm->error);
                        }
                        $stmt_update_rm->close();
                    } else {
                        // If not exists, insert new (e.g., status still 'menunggu' but doctor wants to add initial diagnosis)
                        $insert_rm_sql = "INSERT INTO rekam_medis (id_kunjungan, id_dokter, id_pasien, tgl_rekam_medis, diagnosa, tindakan) VALUES (?, ?, ?, CURDATE(), ?, ?)"; // Ganti keterangan
                        $stmt_insert_rm = $conn->prepare($insert_rm_sql);
                        
                        if (empty($doctor_id) || empty($id_pasien)) {
                            throw new Exception("ID Dokter atau ID Pasien tidak valid untuk Rekam Medis baru.");
                        }
                        $stmt_insert_rm->bind_param("iiiss", $id_kunjungan, $doctor_id, $id_pasien, $diagnosa, $tindakan); // Ganti keterangan
                        if (!$stmt_insert_rm->execute()) {
                            throw new Exception("Gagal menyisipkan rekam medis baru: " . $stmt_insert_rm->error);
                        }
                        $stmt_insert_rm->close();
                    }
                    
                    $conn->commit(); 
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal kunjungan dan Rekam Medis berhasil diperbarui!</div>';

                } catch (Exception $e) {
                    $conn->rollback(); 
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . $e->getMessage() . '</div>';
                }
                break;
                
            case 'delete':
                $id_kunjungan = $_POST['id_kunjungan'];
                
                // Start transaction for delete
                $conn->begin_transaction();
                try {
                    // Delete related rekam medis first (if exists)
                    $stmt_delete_rm = $conn->prepare("DELETE FROM rekam_medis WHERE id_kunjungan = ?");
                    $stmt_delete_rm->bind_param("i", $id_kunjungan);
                    if (!$stmt_delete_rm->execute()) {
                        throw new Exception("Gagal menghapus rekam medis terkait: " . $stmt_delete_rm->error);
                    }
                    $stmt_delete_rm->close();

                    // Then delete the visit
                    $stmt = $conn->prepare("DELETE FROM kunjungan WHERE id_kunjungan = ?");
                    $stmt->bind_param("i", $id_kunjungan);
                    if (!$stmt->execute()) {
                        throw new Exception("Gagal menghapus jadwal kunjungan: " . $stmt->error);
                    }
                    $stmt->close();

                    $conn->commit(); 
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal kunjungan berhasil dihapus!</div>';

                } catch (Exception $e) {
                    $conn->rollback(); 
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . $e->getMessage() . '</div>';
                }
                break;

            case 'complete_visit': 
                $id_kunjungan = $_POST['id_kunjungan_complete'];
                $stmt = $conn->prepare("UPDATE kunjungan SET status_kunjungan = 'selesai' WHERE id_kunjungan = ?");
                $stmt->bind_param("i", $id_kunjungan);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Kunjungan berhasil diselesaikan!</div>';
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menyelesaikan kunjungan: ' . $conn->error . '</div>';
                }
                $stmt->close();
                break;
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : ''; 

$search_condition = "";
$params = [];
$types = "";

if (!empty($doctor_info) && isset($doctor_info['id_poli'])) {
    $search_condition .= "WHERE k.id_poli = ?";
    $params[] = $doctor_info['id_poli'];
    $types .= "i";
}

if (!empty($search)) {
    $search_condition .= (!empty($search_condition) ? " AND " : " WHERE ");
    $search_condition .= "(p.nm_pasien LIKE ? OR p.no_rm LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $types .= "ss";
}

if (!empty($date_filter)) {
    $search_condition .= (!empty($search_condition) ? " AND " : " WHERE ");
    $search_condition .= "k.tgl_kunjungan = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $search_condition .= (!empty($search_condition) ? " AND " : " WHERE ");
    $search_condition .= "k.status_kunjungan = ?";
    $params[] = $status_filter;
    $types .= "s";
}


// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records with search/filter conditions
$total_records_query = "SELECT COUNT(*) as count 
                            FROM kunjungan k
                            JOIN pasien p ON k.id_pasien = p.id_pasien
                            JOIN poliklinik pol ON k.id_poli = pol.id_poli
                            LEFT JOIN rekam_medis rm ON k.id_kunjungan = rm.id_kunjungan
                            $search_condition";
$stmt_count = $conn->prepare($total_records_query);
if (!empty($params)) {
    // If using PHP < 8.0, pass by reference for bind_param
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        $ref_params = [];
        foreach ($params as $key => $value) {
            $ref_params[$key] = &$params[$key];
        }
        call_user_func_array([$stmt_count, 'bind_param'], array_merge([$types], $ref_params));
    } else {
        $stmt_count->bind_param($types, ...$params);
    }
}
$stmt_count->execute();
$total_records_result = $stmt_count->get_result();
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);
$stmt_count->close();


// Get visits with search/filter and pagination
$sql = "SELECT k.*, p.nm_pasien, p.no_rm, pol.nm_poli, 
                rm.diagnosa, rm.tindakan  -- Ganti keterangan
        FROM kunjungan k
        JOIN pasien p ON k.id_pasien = p.id_pasien
        JOIN poliklinik pol ON k.id_poli = pol.id_poli
        LEFT JOIN rekam_medis rm ON k.id_kunjungan = rm.id_kunjungan 
        $search_condition
        ORDER BY k.tgl_kunjungan DESC, k.jam_kunjungan DESC
        LIMIT ?, ?";

$stmt_visits = $conn->prepare($sql);
$pagination_params = $params; 
$pagination_params[] = $offset;
$pagination_params[] = $limit;
$pagination_types = $types . "ii"; 

if (!empty($pagination_params)) {
    // If using PHP < 8.0, pass by reference for bind_param
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        $ref_pagination_params = [];
        foreach ($pagination_params as $key => $value) {
            $ref_pagination_params[$key] = &$pagination_params[$key];
        }
        call_user_func_array([$stmt_visits, 'bind_param'], array_merge([$pagination_types], $ref_pagination_params));
    } else {
        $stmt_visits->bind_param($pagination_types, ...$pagination_params);
    }
}
$stmt_visits->execute();
$result = $stmt_visits->get_result();
$stmt_visits->close();


// Get patients for dropdown (for Add/Edit Modal)
// Reset patients query for new use
$patients_query = $conn->query("SELECT id_pasien, nm_pasien, no_rm FROM pasien ORDER BY nm_pasien");
$patients = [];
if ($patients_query) {
    while($row = $patients_query->fetch_assoc()) {
        $patients[] = $row;
    }
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        /* Tambahkan style untuk status dan tombol */
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            color: #fff;
        }
        .status-menunggu { background-color: #ffc107; color: #333;} 
        .status-selesai { background-color: #28a745; } 
        .status-dibatalkan { background-color: #dc3545; } 
        
        .action-buttons button {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        /* Modal styling (pastikan sudah ada di style.css atau tambahkan di sini jika belum) */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 600px; 
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
            animation-name: animatetop;
            animation-duration: 0.4s
        }

        @keyframes animatetop {
            from {top: -300px; opacity: 0}
            to {top: 0; opacity: 1}
        }

        .modal-header {
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .modal-body {
            padding: 20px 0;
        }

        .modal-footer {
            padding-top: 10px;
            border-top: 1px solid #eee;
            text-align: right;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group select,
        .form-group textarea { 
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }

        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="content-header">
            <h2><i class="fas fa-calendar-check"></i> <?php echo $page_title; ?></h2>
            <button class="btn btn-primary" onclick="openModal('addModal')">
                <i class="fas fa-plus"></i> Tambah Jadwal
            </button>
        </div>
        
        <?php echo $message; ?>
        
        <div class="table-filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari pasien..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <input type="date" id="dateFilter" value="<?php echo htmlspecialchars($date_filter); ?>" onchange="filterByDate()">
                <select id="statusFilter" onchange="filterByStatus()">
                    <option value="">Semua Status</option>
                    <option value="menunggu" <?php echo ($status_filter == 'menunggu' ? 'selected' : ''); ?>>Menunggu</option>
                    <option value="selesai" <?php echo ($status_filter == 'selesai' ? 'selected' : ''); ?>>Selesai</option>
                    <option value="dibatalkan" <?php echo ($status_filter == 'dibatalkan' ? 'selected' : ''); ?>>Dibatalkan</option>
                </select>
                <button class="btn btn-outline-primary" onclick="exportData()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-outline-secondary" onclick="printData()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        
        <div class="table-container">
            <table class="data-table" id="visitsTable">
                <thead>
                    <tr>
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>Poliklinik</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['no_rm']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                            <td><?php echo htmlspecialchars($row['nm_poli']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tgl_kunjungan'])); ?></td>
                            <td><?php echo htmlspecialchars($row['jam_kunjungan']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(htmlspecialchars($row['status_kunjungan'])); ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['status_kunjungan'])); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button class="btn btn-info btn-sm" onclick="viewVisit(<?php echo $row['id_kunjungan']; ?>)">
                                    <i class="fas fa-eye"></i> Lihat
                                </button>
                                <button class="btn btn-warning btn-sm" 
                                    onclick="editVisit(
                                        <?php echo $row['id_kunjungan']; ?>,
                                        '<?php echo htmlspecialchars($row['id_pasien']); ?>',
                                        '<?php echo htmlspecialchars($row['tgl_kunjungan']); ?>',
                                        '<?php echo htmlspecialchars($row['jam_kunjungan']); ?>',
                                        '<?php echo htmlspecialchars($row['diagnosa'] ?? ''); ?>', 
                                        '<?php echo htmlspecialchars($row['tindakan'] ?? ''); ?>' // Ganti keterangan
                                    )">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($row['status_kunjungan'] == 'menunggu'): ?>
                                <button class="btn btn-success btn-sm" onclick="confirmCompleteVisit(<?php echo $row['id_kunjungan']; ?>, '<?php echo htmlspecialchars($row['nm_pasien']); ?>')">
                                    <i class="fas fa-check"></i> Selesai
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-danger btn-sm" onclick="deleteVisit(<?php echo $row['id_kunjungan']; ?>, '<?php echo htmlspecialchars($row['nm_pasien']); ?>')">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data kunjungan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>" class="page-link">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>" 
               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_filter=<?php echo urlencode($date_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>" class="page-link">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-plus"></i> Tambah Jadwal Kunjungan</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Pasien</label>
                        <select name="id_pasien" required>
                            <option value="">Pilih Pasien</option>
                            <?php 
                            if (!empty($patients)) {
                                foreach ($patients as $patient): 
                            ?>
                            <option value="<?php echo htmlspecialchars($patient['id_pasien']); ?>">
                                <?php echo htmlspecialchars($patient['no_rm'] . ' - ' . $patient['nm_pasien']); ?>
                            </option>
                            <?php 
                                endforeach; 
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Tanggal Kunjungan</label>
                        <input type="date" name="tgl_kunjungan" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Jam Kunjungan</label>
                        <input type="time" name="jam_kunjungan" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-stethoscope"></i> Diagnosa</label>
                        <textarea name="diagnosa" id="add_diagnosa" rows="3" placeholder="Masukkan diagnosa medis"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-notes-medical"></i> Tindakan</label> <textarea name="tindakan" id="add_tindakan" rows="3" placeholder="Masukkan tindakan medis yang dilakukan"></textarea> </div>
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
                <h3><i class="fas fa-calendar-edit"></i> Edit Jadwal Kunjungan</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_kunjungan" id="edit_id_kunjungan">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Pasien</label>
                        <select name="id_pasien" id="edit_id_pasien" required>
                            <option value="">Pilih Pasien</option>
                            <?php 
                            if (!empty($patients)) {
                                foreach ($patients as $patient): 
                            ?>
                            <option value="<?php echo htmlspecialchars($patient['id_pasien']); ?>">
                                <?php echo htmlspecialchars($patient['no_rm'] . ' - ' . $patient['nm_pasien']); ?>
                            </option>
                            <?php 
                                endforeach; 
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Tanggal Kunjungan</label>
                        <input type="date" name="tgl_kunjungan" id="edit_tgl_kunjungan" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Jam Kunjungan</label>
                        <input type="time" name="jam_kunjungan" id="edit_jam_kunjungan" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-stethoscope"></i> Diagnosa</label>
                        <textarea name="diagnosa" id="edit_diagnosa" rows="3" placeholder="Masukkan diagnosa medis"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-notes-medical"></i> Tindakan</label> <textarea name="tindakan" id="edit_tindakan" rows="3" placeholder="Masukkan tindakan medis yang dilakukan"></textarea> </div>
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
                <h3><i class="fas fa-info-circle"></i> Detail Kunjungan</h3>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="visitDetails">
                    </div>
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
                <p>Apakah Anda yakin ingin menghapus jadwal kunjungan <strong id="deletePatientName"></strong> ini?</p>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_kunjungan" id="delete_id_kunjungan">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="completeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-check-circle"></i> Selesaikan Kunjungan</h3>
                <span class="close" onclick="closeModal('completeModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Anda akan menandai kunjungan pasien <strong id="completePatientName"></strong> sebagai selesai. Lanjutkan?</p>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="complete_visit">
                    <input type="hidden" name="id_kunjungan_complete" id="complete_id_kunjungan">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('completeModal')">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Selesaikan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Fungsi untuk membuka dan menutup modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        function editVisit(id_kunjungan, id_pasien, tgl_kunjungan, jam_kunjungan, diagnosa, tindakan) { // Ganti keterangan
            document.getElementById('edit_id_kunjungan').value = id_kunjungan;
            document.getElementById('edit_id_pasien').value = id_pasien;
            document.getElementById('edit_tgl_kunjungan').value = tgl_kunjungan;
            document.getElementById('edit_jam_kunjungan').value = jam_kunjungan;
            document.getElementById('edit_diagnosa').value = diagnosa;
            document.getElementById('edit_tindakan').value = tindakan; // Ganti keterangan
            openModal('editModal');
        }

        function deleteVisit(id_kunjungan, nm_pasien) {
            document.getElementById('delete_id_kunjungan').value = id_kunjungan;
            document.getElementById('deletePatientName').textContent = nm_pasien;
            openModal('deleteModal');
        }

        function confirmCompleteVisit(id_kunjungan, nm_pasien) {
            document.getElementById('complete_id_kunjungan').value = id_kunjungan;
            document.getElementById('completePatientName').textContent = nm_pasien;
            openModal('completeModal');
        }

        function viewVisit(id_kunjungan) {
            $.ajax({
                url: 'get_visit_details.php', // Buat file PHP baru untuk ini
                type: 'GET',
                data: { id_kunjungan: id_kunjungan },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.status === 'success') {
                        var detailsHtml = `
                            <p><strong>No. RM:</strong> ${data.data.no_rm}</p>
                            <p><strong>Nama Pasien:</strong> ${data.data.nm_pasien}</p>
                            <p><strong>Poliklinik:</strong> ${data.data.nm_poli}</p>
                            <p><strong>Tanggal Kunjungan:</strong> ${new Date(data.data.tgl_kunjungan).toLocaleDateString('id-ID')}</p>
                            <p><strong>Jam Kunjungan:</strong> ${data.data.jam_kunjungan}</p>
                            <p><strong>Status Kunjungan:</strong> <span class="status-badge status-${data.data.status_kunjungan.toLowerCase()}">${data.data.status_kunjungan.charAt(0).toUpperCase() + data.data.status_kunjungan.slice(1)}</span></p>
                            <p><strong>Diagnosa:</strong> ${data.data.diagnosa ? data.data.diagnosa : '-'}</p>
                            <p><strong>Tindakan:</strong> ${data.data.tindakan ? data.data.tindakan : '-'}</p> `;
                        document.getElementById('visitDetails').innerHTML = detailsHtml;
                        openModal('viewModal');
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Terjadi kesalahan saat mengambil detail kunjungan: ' + error);
                }
            });
        }

        function applyFilters() {
            var search = document.getElementById('searchInput').value;
            var dateFilter = document.getElementById('dateFilter').value;
            var statusFilter = document.getElementById('statusFilter').value;
            window.location.href = `?search=${encodeURIComponent(search)}&date_filter=${encodeURIComponent(dateFilter)}&status_filter=${encodeURIComponent(statusFilter)}`;
        }

        document.getElementById('searchInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        function filterByDate() {
            applyFilters();
        }

        function filterByStatus() {
            applyFilters();
        }

        function exportData() {
            // Implement export logic here, e.g., redirect to an export script
            alert('Fungsi Export belum diimplementasikan.');
        }

        function printData() {
            // Implement print logic here
            alert('Fungsi Print belum diimplementasikan.');
        }
    </script>
</body>
</html>