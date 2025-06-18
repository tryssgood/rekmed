<?php
session_start();
include 'config/database.php';
include 'includes/functions.php';

// Cek login dan role dokter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dokter') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$doctor_id = getDoctorId($conn, $user_id);

if ($doctor_id === null) {
    $_SESSION['error_message'] = "ID Dokter tidak ditemukan. Harap hubungi administrator.";
    header("Location: index.php"); // Redirect ke dashboard atau halaman error
    exit();
}

$kunjungan_id = null;
$patient_info = null;
$kunjungan_info = null;
// Inisialisasi $view_mode secara default ke false (mode input/edit)
$view_mode = false; 

// --- PENANGANAN UTAMA: Ambil kunjungan_id dari GET atau POST ---
if (isset($_GET['kunjungan_id'])) {
    $kunjungan_id = (int)$_GET['kunjungan_id'];
    // Jika parameter 'view' ada dan bernilai 'true', baru set $view_mode menjadi true
    if (isset($_GET['view']) && $_GET['view'] === 'true') {
        $view_mode = true;
    }
} elseif (isset($_POST['id_kunjungan'])) { // Jika formulir disubmit dan ada id_kunjungan di POST
    $kunjungan_id = (int)$_POST['id_kunjungan'];
    // Penting: Saat POST, kita selalu berasumsi ini adalah mode EDIT/INPUT, jadi $view_mode tetap false.
    // Tidak perlu cek $_POST['view'] karena form submit tidak akan punya itu
}

// Jika kunjungan_id tidak diberikan sama sekali, tampilkan pesan kesalahan dan keluar
if ($kunjungan_id === null || $kunjungan_id <= 0) {
    $_SESSION['error_message'] = "ID Kunjungan tidak diberikan atau tidak valid. Silakan pilih pasien dari daftar.";
    header("Location: index.php"); // Redirect ke dashboard atau daftar pasien
    exit();
}

// Ambil informasi kunjungan dan pasien berdasarkan $kunjungan_id
// Pastikan ID DOKTER juga difilter di sini untuk keamanan
$stmt_kunjungan_info = $conn->prepare("
    SELECT k.*, p.nm_pasien, p.no_rm, p.tgl_lhr, p.alamat
    FROM kunjungan k
    JOIN pasien p ON k.id_pasien = p.id_pasien
    WHERE k.id_kunjungan = ? AND k.id_dokter = ?
");
$stmt_kunjungan_info->bind_param("ii", $kunjungan_id, $doctor_id);
$stmt_kunjungan_info->execute();
$result_kunjungan_info = $stmt_kunjungan_info->get_result();

if ($result_kunjungan_info->num_rows > 0) {
    $kunjungan_info = $result_kunjungan_info->fetch_assoc();
    $patient_info = [
        'id_pasien' => $kunjungan_info['id_pasien'],
        'nm_pasien' => $kunjungan_info['nm_pasien'],
        'no_rm' => $kunjungan_info['no_rm'],
        'tgl_lhr' => $kunjungan_info['tgl_lhr'],
        'alamat' => $kunjungan_info['alamat']
    ];
} else {
    $_SESSION['error_message'] = "Data kunjungan tidak ditemukan atau Anda tidak memiliki akses ke kunjungan ini.";
    header("Location: index.php");
    exit();
}
$stmt_kunjungan_info->close();

// --- PROSES FORM SUBMISSION (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$view_mode) { // Pastikan tidak dalam mode view saat submit
    // Ambil data dari form POST
    $kunjungan_id_post = $_POST['id_kunjungan'];
    $keluhan = trim($_POST['keluhan']);
    $tindakan_resep = trim($_POST['tindakan_resep']);

    // Validasi sederhana
    if (empty($keluhan)) {
        $_SESSION['error_message'] = "Keluhan (Diagnosa) tidak boleh kosong.";
        // Jangan redirect, agar form tetap diisi dan user bisa memperbaiki
    } else {
        // UPDATE tabel kunjungan
        // Pastikan ID DOKTER juga difilter di sini saat UPDATE
        $sql_update_kunjungan = "UPDATE kunjungan SET keluhan = ?, tindakan_resep = ?, updated_at = CURRENT_TIMESTAMP WHERE id_kunjungan = ? AND id_dokter = ?";
        $stmt_update_kunjungan = $conn->prepare($sql_update_kunjungan);
        $stmt_update_kunjungan->bind_param("ssii", $keluhan, $tindakan_resep, $kunjungan_id_post, $doctor_id);

        if ($stmt_update_kunjungan->execute()) {
            $_SESSION['success_message'] = "Rekam medis kunjungan berhasil diperbarui!";
            header("Location: index.php"); // Redirect kembali ke dashboard
            exit();
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui rekam medis: " . $stmt_update_kunjungan->error;
        }
        $stmt_update_kunjungan->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $view_mode ? 'Lihat Rekam Medis' : 'Input Rekam Medis'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="content">
            <div class="form-card">
                <h2><?php echo $view_mode ? 'Detail Rekam Medis' : 'Input Rekam Medis'; ?> untuk Pasien: <?php echo htmlspecialchars($patient_info['nm_pasien'] ?? 'N/A'); ?></h2>
                <p>No. RM: <?php echo htmlspecialchars($patient_info['no_rm'] ?? 'N/A'); ?></p>
                <p>Tanggal Kunjungan: <?php echo htmlspecialchars(formatDate($kunjungan_info['tgl_kunjungan'] ?? '') ?? 'N/A'); ?></p>
                <p>Jam Kunjungan: <?php echo htmlspecialchars($kunjungan_info['jam_kunjungan'] ?? 'N/A'); ?></p>

                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert success">' . $_SESSION['success_message'] . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert error">' . $_SESSION['error_message'] . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>
                <form action="rekam_medis_form.php" method="POST">
                    <input type="hidden" name="id_kunjungan" value="<?php echo htmlspecialchars($kunjungan_id); ?>">

                    <div class="form-group">
                        <label for="keluhan">Keluhan (Diagnosa):</label>
                        <textarea id="keluhan" name="keluhan" rows="5" <?php echo $view_mode ? 'readonly' : 'required'; ?>><?php echo htmlspecialchars($kunjungan_info['keluhan'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="tindakan_resep">Tindakan / Resep Obat:</label>
                        <textarea id="tindakan_resep" name="tindakan_resep" rows="5" <?php echo $view_mode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($kunjungan_info['tindakan_resep'] ?? ''); ?></textarea>
                    </div>

                    <?php if (!$view_mode): // Tombol ini hanya muncul jika TIDAK dalam mode view ?>
                    <button type="submit" class="btn btn-primary">Simpan Rekam Medis</button>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary">Kembali ke Dashboard</a>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>