<?php
session_start();
include '../config/database.php'; // Tetap butuh file ini untuk koneksi database

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Manajemen Rekam Medis";

// Handle form submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $no_rm = $_POST['no_rm'];
                $tanggal = $_POST['tanggal'];
                $keluhan = $_POST['keluhan'];
                $diagnosa = $_POST['diagnosa'];
                $resep = $_POST['resep'];
                $dokter = $_POST['dokter'];

                $stmt = $conn->prepare("INSERT INTO medical_records (no_rm, tanggal, keluhan, diagnosa, resep, dokter) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $no_rm, $tanggal, $keluhan, $diagnosa, $resep, $dokter);

                if ($stmt->execute()) {
                    $success = "Data rekam medis berhasil ditambahkan.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                break;

            case 'edit':
                $id_rekam_medis = $_POST['id_rekam_medis'];
                $no_rm = $_POST['no_rm'];
                $tanggal = $_POST['tanggal'];
                $keluhan = $_POST['keluhan'];
                $diagnosa = $_POST['diagnosa'];
                $resep = $_POST['resep'];
                $dokter = $_POST['dokter'];

                $stmt = $conn->prepare("UPDATE medical_records SET no_rm = ?, tanggal = ?, keluhan = ?, diagnosa = ?, resep = ?, dokter = ? WHERE id_rekam_medis = ?");
                $stmt->bind_param("ssssssi", $no_rm, $tanggal, $keluhan, $diagnosa, $resep, $dokter, $id_rekam_medis);

                if ($stmt->execute()) {
                    $success = "Data rekam medis berhasil diperbarui.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                break;

            case 'delete':
                $id_rekam_medis = $_POST['id_rekam_medis'];

                $stmt = $conn->prepare("DELETE FROM medical_records WHERE id_rekam_medis = ?");
                $stmt->bind_param("i", $id_rekam_medis);

                if ($stmt->execute()) {
                    $success = "Data rekam medis berhasil dihapus.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                break;
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$per_page = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(mr.no_rm LIKE ? OR p.nm_pasien LIKE ? OR mr.diagnosa LIKE ? OR mr.keluhan LIKE ? OR mr.dokter LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= "sssss";
}

if (!empty($date_from)) {
    $where_conditions[] = "mr.tanggal >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "mr.tanggal <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM medical_records mr
              LEFT JOIN pasien p ON mr.no_rm = p.no_rm
              $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $per_page);

// Get medical record data
$sql = "SELECT mr.*, p.nm_pasien
        FROM medical_records mr
        LEFT JOIN pasien p ON mr.no_rm = p.no_rm
        $where_clause
        ORDER BY mr.tanggal DESC, mr.created_at DESC
        LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $medical_records_data = $stmt->get_result();
} else {
    $medical_records_data = $conn->query($sql);
}

// Get patients for dropdown (for Add/Edit forms)
$patients = $conn->query("SELECT no_rm, nm_pasien FROM pasien ORDER BY nm_pasien");
$patients_for_dropdown = [];
while ($row = $patients->fetch_assoc()) {
    $patients_for_dropdown[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistem Rekam Medis</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS Inlined Here */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f7f6;
            color: #333;
        }

        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background-color: #537D5D; /* Warna hijau gelap */
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .user-info {
            margin-right: 15px;
            font-size: 16px;
        }

        .user-info i {
            margin-right: 5px;
        }

        .btn-logout {
            background-color: #e74c3c;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .btn-logout:hover {
            background-color: #c0392b;
        }

        /* Content Header */
        .content-header {
            background-color: #ffffff;
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .content-header h2 {
            margin: 0;
            color: #537D5D;
        }

        .content-header h2 i {
            margin-right: 10px;
            color: #537D5D;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        /* Buttons */
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-primary {
            background-color: #28a745; /* Green */
            color: white;
        }

        .btn-primary:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #6c757d; /* Grey */
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        .btn-tertiary {
            background-color: #007bff; /* Blue for Print/Export */
            color: white;
        }

        .btn-tertiary:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        .btn-outline {
            background-color: transparent;
            color: #6c757d;
            border: 1px solid #6c757d;
        }

        .btn-outline:hover {
            background-color: #6c757d;
            color: white;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 4px;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529; /* Dark text for warning */
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        /* Alerts */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Filter Section */
        .filter-section {
            background-color: #ffffff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex; /* Untuk menempatkan search dan actions di satu baris */
            justify-content: space-between;
            align-items: center;
            gap: 15px; /* Spasi antara search form dan actions */
        }

        .search-form {
            display: flex;
            flex-grow: 1; /* Agar search form mengambil ruang yang tersedia */
            max-width: 500px; /* Batasi lebar search form */
        }

        .search-group {
            display: flex;
            width: 100%;
        }

        .search-group input[type="text"] {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 5px 0 0 5px;
            font-size: 15px;
        }

        .search-group button {
            border-radius: 0 5px 5px 0;
            margin-left: -1px; /* Overlap border */
        }

        .search-actions {
            display: flex;
            gap: 10px;
        }

        /* Table */
        .table-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow-x: auto; /* For responsive tables */
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table thead th {
            background-color: #537D5D; /* Hijau gelap */
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background-color: #e2e6ea;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap; /* Untuk tombol aksi yang lebih dari satu agar rapi */
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 10px;
        }

        .pagination-info {
            font-size: 14px;
            color: #666;
        }

        /* Modals */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.5); /* Black w/ opacity */
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* 5% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 90%; /* Could be more responsive */
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }

        .modal-lg {
            max-width: 900px; /* Larger modal */
        }

        .modal-header {
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            color: #537D5D;
        }

        .modal-header h3 i {
            margin-right: 8px;
        }

        .modal-footer {
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: right;
            margin-top: 20px;
        }

        .modal-footer button {
            margin-left: 10px;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Forms in Modal */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .form-group label i {
            margin-right: 5px;
            color: #537D5D;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: calc(100% - 22px); /* Adjust for padding and border */
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
        }

        .form-group textarea {
            resize: vertical;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0; /* Remove margin-bottom from individual form-groups in a row */
        }

        .text-center {
            text-align: center;
        }

        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }

        /* Specific styles for medical record detail view if you were to implement one */
        /* (Similar to the lab detail view in your original code) */
        .record-detail {
            margin-top: 15px;
        }

        .record-detail .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .record-detail .detail-row strong {
            min-width: 120px;
            color: #537D5D;
        }

        .record-detail .detail-section {
            margin-bottom: 15px;
        }

        .record-detail .detail-section strong {
            display: block;
            margin-bottom: 8px;
            color: #537D5D;
        }

        .record-content {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            white-space: pre-line;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        /* FOOTER STYLES */
        .footer {
            background-color: #537D5D; /* Warna hijau gelap yang sama dengan header */
            color: white;
            padding: 15px 20px;
            text-align: center; /* Untuk menengahkan teks */
            margin-top: 30px; /* Beri sedikit jarak dari konten utama */
            border-radius: 8px; /* Sudut membulat */
            box-shadow: 0 -2px 4px rgba(0,0,0,0.05); /* Bayangan ke atas */
        }

        .footer p {
            margin: 0; /* Hapus margin default pada paragraf */
            font-size: 14px;
        }
        /* END FOOTER STYLES */


        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            .header-right {
                flex-direction: column;
                gap: 10px;
            }
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            .search-form {
                max-width: 100%;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .form-row .form-group {
                margin-bottom: 15px;
            }
            .data-table th, .data-table td {
                padding: 8px 10px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>Sistem Rekam Medis</h1>
        </div>
        <div class="header-right">
            <span class="user-info">
                <i class="fas fa-user-circle"></i> alifalif (Admin)
            </span>
            <a href="../logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    <div class="navbar">
        </div>

    <div class="container">
        <div class="content">
            <div class="content-header">
                <h2><i class="fas fa-notes-medical"></i> <?php echo $page_title; ?></h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addModal')">
                        <i class="fas fa-plus"></i> Tambah Rekam Medis
                    </button>
                    </div>
            </div>

            <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <div class="filter-section">
                <form method="GET" class="search-form">
                    <div class="search-group">
                        <input type="text" name="search" placeholder="Cari pasien, no. RM, diagnosa..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                <div class="search-actions">
                    <button class="btn btn-tertiary" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak
                    </button>
                    <button class="btn btn-tertiary" onclick="exportTableToCSV('rekam_medis.csv')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NO. RM</th>
                            <th>NAMA PASIEN</th>
                            <th>TANGGAL</th>
                            <th>KELUHAN</th>
                            <th>DIAGNOSA</th>
                            <th>RESEP</th>
                            <th>DOKTER</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($medical_records_data->num_rows > 0): ?>
                        <?php $i = $offset + 1; while ($record = $medical_records_data->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['no_rm']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($record['nm_pasien'] ?? 'Tidak ditemukan'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($record['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars(substr($record['keluhan'], 0, 50)); ?><?php if (strlen($record['keluhan']) > 50): ?>...<?php endif; ?></td>
                            <td><?php echo htmlspecialchars(substr($record['diagnosa'], 0, 50)); ?><?php if (strlen($record['diagnosa']) > 50): ?>...<?php endif; ?></td>
                            <td><?php echo htmlspecialchars(substr($record['resep'], 0, 50)); ?><?php if (strlen($record['resep']) > 50): ?>...<?php endif; ?></td>
                            <td><?php echo htmlspecialchars($record['dokter']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-sm btn-warning" onclick="editRecord(<?php echo htmlspecialchars(json_encode($record)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-sm btn-danger" onclick="deleteRecord(<?php echo $record['id_rekam_medis']; ?>, '<?php echo htmlspecialchars($record['no_rm']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">Tidak ada data rekam medis.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn btn-outline">
                    <i class="fas fa-chevron-left"></i> Sebelumnya
                </a>
                <?php endif; ?>

                <span class="pagination-info">
                    Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                    (<?php echo $total_records; ?> total data)
                </span>

                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn btn-outline">
                    Selanjutnya <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Rekam Medis</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="add_no_rm"><i class="fas fa-id-card"></i> No. Rekam Medis</label>
                            <select id="add_no_rm" name="no_rm" required>
                                <option value="">-- Pilih Pasien --</option>
                                <?php foreach ($patients_for_dropdown as $patient): ?>
                                <option value="<?php echo $patient['no_rm']; ?>">
                                    <?php echo $patient['no_rm']; ?> - <?php echo $patient['nm_pasien']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="add_tanggal"><i class="fas fa-calendar"></i> Tanggal</label>
                            <input type="date" id="add_tanggal" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add_keluhan"><i class="fas fa-notes-medical"></i> Keluhan</label>
                        <textarea id="add_keluhan" name="keluhan" rows="3" placeholder="Deskripsi keluhan pasien..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="add_diagnosa"><i class="fas fa-diagnoses"></i> Diagnosa</label>
                        <textarea id="add_diagnosa" name="diagnosa" rows="3" placeholder="Hasil diagnosa dokter..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="add_resep"><i class="fas fa-prescription-bottle-alt"></i> Resep Obat</label>
                        <textarea id="add_resep" name="resep" rows="3" placeholder="Contoh: Paracetamol 3x1 tablet, Vitamin C 2x1 tablet"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="add_dokter"><i class="fas fa-user-md"></i> Dokter</label>
                        <input type="text" id="add_dokter" name="dokter" placeholder="Nama dokter yang menangani" required>
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
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Rekam Medis</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id_rekam_medis" name="id_rekam_medis">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_no_rm"><i class="fas fa-id-card"></i> No. Rekam Medis</label>
                            <select id="edit_no_rm" name="no_rm" required>
                                <option value="">-- Pilih Pasien --</option>
                                <?php
                                // Re-fetch patients to ensure the list is fresh if needed, or pass $patients_for_dropdown
                                foreach ($patients_for_dropdown as $patient):
                                ?>
                                <option value="<?php echo $patient['no_rm']; ?>">
                                    <?php echo $patient['no_rm']; ?> - <?php echo $patient['nm_pasien']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_tanggal"><i class="fas fa-calendar"></i> Tanggal</label>
                            <input type="date" id="edit_tanggal" name="tanggal" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_keluhan"><i class="fas fa-notes-medical"></i> Keluhan</label>
                        <textarea id="edit_keluhan" name="keluhan" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_diagnosa"><i class="fas fa-diagnoses"></i> Diagnosa</label>
                        <textarea id="edit_diagnosa" name="diagnosa" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_resep"><i class="fas fa-prescription-bottle-alt"></i> Resep Obat</label>
                        <textarea id="edit_resep" name="resep" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_dokter"><i class="fas fa-user-md"></i> Dokter</label>
                        <input type="text" id="edit_dokter" name="dokter" required>
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

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-trash"></i> Konfirmasi Hapus</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id_rekam_medis" name="id_rekam_medis">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data rekam medis untuk No. RM <strong id="delete_no_rm"></strong>?</p>
                    <p class="text-danger">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Sistem Rekam Medis. All rights reserved.</p>
    </div>

    <script>
        /* JavaScript Inlined Here */
        function editRecord(record) {
            document.getElementById('edit_id_rekam_medis').value = record.id_rekam_medis;
            document.getElementById('edit_no_rm').value = record.no_rm;
            document.getElementById('edit_tanggal').value = record.tanggal; // Tanggal sudah dalam format YYYY-MM-DD dari DB
            document.getElementById('edit_keluhan').value = record.keluhan;
            document.getElementById('edit_diagnosa').value = record.diagnosa;
            document.getElementById('edit_resep').value = record.resep || '';
            document.getElementById('edit_dokter').value = record.dokter;
            openModal('editModal');
        }

        function deleteRecord(id, no_rm) {
            document.getElementById('delete_id_rekam_medis').value = id;
            document.getElementById('delete_no_rm').textContent = no_rm;
            openModal('deleteModal');
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
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
        
        // Export to CSV function
        function exportTableToCSV(filename) {
            const csv = [];
            const rows = document.querySelectorAll("table.data-table tr");

            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                    data = data.replace(/"/g, '""'); // Escape double quotes
                    row.push('"' + data + '"');
                }
                csv.push(row.join(';')); // Use semicolon as separator for CSV compatibility
            }

            // Download CSV file
            const csv_string = csv.join('\n');
            const link = document.createElement('a');
            link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv_string);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>